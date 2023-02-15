@extends('layouts.app')
@section('content')
<table style="background: #fff; width: 650px; margin: auto; text-align: center;background:#fff;">
         <tr>
            <td>
               <table border="0" cellpadding="0" cellspacing="0" width="525" style="width: 525px; margin: auto; font-size: 14px; font-family: \'MuseoSans-300\';">
                  <tr>
                     
                  </tr>
                  <tr>
                     <td>
                        <table style="background: #fff; color: #363636; font-size: 16px; padding: 30px 25px 10px; line-height: 24px; text-align: center; width:525px;">
                           <tr>
                              <td>
                                 <h2 style="color: #333; font-family: \'MuseoSans-500\'; padding-bottom: 10px; margin-top: 10px">Reset your password
                                 </h2>
                                 <p style="font-size:18px;">Hi {{$user_name}} .</p>
                                 <p>We received a request to change your password. Please<br> click on the button below to create a new password.</p>
                                 <p style="text-align:center; padding:20px 0 20px 0;">
                                    <a href="{{$reset_link}}" style="color:#fff;border: none; background-color: #f69a34;border-top: 12px solid #f69a34;border-right: 24px solid #f69a34;border-bottom: 12px solid #f69a34;border-left: 24px solid #f69a34; border-radius: 10px;font-size:18px;text-decoration: none;font-family: \'MuseoSans-100\'">RESET YOUR PASSWORD
                                    </a>
                                 </p>
                                 <p style="font-size:13px; font-style:italic;">or copy this link: <a href="{{$reset_link}}" style="color: #0069aa; text-decoration: none;">{{$reset_link}}</a>
                                 </p>
                                 
                              </td>
                           </tr>
                        </table>
                     </td>
                  </tr>
               </table>
            </td>           
         </tr>          
         <tr style="background:#f5f5f5">                
            <td style="text-align: center; color: #a1a1a1; padding: 25px 0 20px; font-family: \'MuseoSans-300\';">                  
               <h3 style="color:#333; font-size:22px;">Need help?</h3>                      
               <p style="margin: 0px; color:#333;">Get in touch by emailing us at</p>
                                      
            </td>       
         </tr>      
         <tr style="background:#ffffff;" height="30">       
            <td>&nbsp;</td>         
         </tr>      
      </table> 
@endsection


